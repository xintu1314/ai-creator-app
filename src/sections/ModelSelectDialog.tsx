import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { Model } from '@/types';

interface ModelSelectDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  models: Model[];
  selectedModel: string;
  onSelectModel: (modelId: string) => void;
  title: string;
}

export default function ModelSelectDialog({
  open,
  onOpenChange,
  models,
  selectedModel,
  onSelectModel,
  title,
}: ModelSelectDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-[680px] p-0 gap-0 overflow-hidden">
        <DialogHeader className="px-6 py-4 border-b border-[#E5E5E5]">
          <DialogTitle className="text-base font-medium text-[#1A1A1A]">{title}</DialogTitle>
        </DialogHeader>
        
        <div className="p-4 max-h-[500px] overflow-y-auto">
          <div className="grid grid-cols-2 gap-3">
            {models.map((model) => (
              <button
                key={model.id}
                onClick={() => {
                  onSelectModel(model.id);
                  onOpenChange(false);
                }}
                className={cn(
                  "text-left p-4 rounded-xl border transition-all duration-200",
                  "hover:border-[#3B82F6] hover:bg-[#F0F7FF]",
                  selectedModel === model.id 
                    ? "border-[#3B82F6] bg-[#F0F7FF]" 
                    : "border-[#E5E5E5] bg-[#F9FAFB]"
                )}
              >
                <div className="flex items-start gap-3">
                  {/* Model Icon */}
                  <div className={cn(
                    "w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0",
                    model.icon === 'banana' 
                      ? "bg-gradient-to-br from-yellow-400 to-yellow-600"
                      : model.icon === 'sora'
                        ? "bg-gradient-to-br from-purple-500 to-pink-500"
                        : model.icon.includes('gradient') 
                          ? model.icon 
                          : "bg-gradient-to-br from-blue-400 to-blue-600"
                  )}>
                    {model.icon === 'seedream' && (
                      <div className="w-5 h-5 bg-white/90 rounded flex items-center justify-center">
                        <div className="w-3 h-3 bg-gradient-to-r from-blue-400 to-blue-600 rounded-sm" />
                      </div>
                    )}
                    {model.icon === 'universal' && (
                      <div className="w-5 h-5 bg-amber-400 rounded-full" />
                    )}
                    {model.icon === 'qwen' && (
                      <div className="w-5 h-5 bg-purple-500 rounded-lg" />
                    )}
                    {model.icon === 'ai' && (
                      <div className="w-5 h-5 bg-pink-500 rounded-full" />
                    )}
                    {model.icon === 'base' && (
                      <div className="w-5 h-5 bg-orange-500 rounded-lg" />
                    )}
                    {model.icon === 'zimage' && (
                      <div className="w-5 h-5 bg-indigo-500 rounded-lg" />
                    )}
                    {model.icon === 'banana' && (
                      <div className="w-5 h-5 bg-white/90 rounded-lg flex items-center justify-center">
                        <div className="w-3 h-3 bg-gradient-to-br from-yellow-300 to-yellow-500 rounded-sm" />
                      </div>
                    )}
                    {model.icon === 'pixverse' && (
                      <div className="w-5 h-5 bg-cyan-400 rounded-full" />
                    )}
                    {model.icon === 'kling' && (
                      <div className="w-5 h-5 bg-blue-500 rounded-full" />
                    )}
                    {model.icon === 'tongyi' && (
                      <div className="w-5 h-5 bg-purple-600 rounded-lg" />
                    )}
                    {model.icon === 'vidu' && (
                      <div className="w-5 h-5 bg-orange-400 rounded-lg" />
                    )}
                    {model.icon === 'hailuo' && (
                      <div className="w-5 h-5 bg-red-500 rounded-full" />
                    )}
                    {model.icon === 'sora' && (
                      <div className="w-5 h-5 bg-gradient-to-br from-purple-500 to-pink-500 rounded-lg" />
                    )}
                  </div>
                  
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-medium text-[#1A1A1A] text-sm">{model.name}</span>
                      {model.isNew && (
                        <Badge className="h-4 px-1.5 text-[10px] bg-amber-100 text-amber-600 hover:bg-amber-100 border-0">
                          NEW
                        </Badge>
                      )}
                    </div>
                    <p className="text-xs text-[#666666] line-clamp-2 mb-2">{model.description}</p>
                    
                    {/* Tags */}
                    <div className="flex flex-wrap gap-1.5">
                      {model.tags.map((tag, idx) => (
                        <span 
                          key={idx}
                          className="px-2 py-0.5 text-[10px] bg-white border border-[#E5E5E5] rounded text-[#666666]"
                        >
                          {tag}
                        </span>
                      ))}
                    </div>
                  </div>
                </div>
              </button>
            ))}
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
